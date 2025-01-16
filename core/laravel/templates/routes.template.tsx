          <Route
            path="/manager/{routeName}"
            element={
              <ProtectedRoute>
                <{modelName}View />
              </ProtectedRoute>
            }
          />
          <Route
            path="/manager/{routeName}/:id"
            element={
              <ProtectedRoute>
                <{modelName}Form />
              </ProtectedRoute>
            }
          />
          <Route
            path="/manager/{routeName}/new"
            element={
              <ProtectedRoute>
                <{modelName}Form />
              </ProtectedRoute>
            }
          />
          <Route
            path="/manager/{routeName}/show/:id"
            element={<ProtectedRoute>{<{modelName}Show />}</ProtectedRoute>}
          />